export const LOGIN_MUTATION = `
  mutation Login($usernameOrEmail: String!, $password: String!) {
    login(usernameOrEmail: $usernameOrEmail, password: $password) {
      accessToken
      refreshToken
      tokenType
      expiresIn
    }
  }
`

export const REGISTER_MUTATION = `
  mutation Register($username: String!, $email: String!, $password: String!) {
    register(username: $username, email: $email, password: $password) {
      id
      username
      email
      displayName
      createdAt
      updatedAt
    }
  }
`

export const LOGOUT_MUTATION = `
  mutation Logout($refreshToken: String!) {
    logout(refreshToken: $refreshToken)
  }
`

export const ME_QUERY = `
  query Me {
    me {
      id
      username
      email
      displayName
      bio
      createdAt
      updatedAt
    }
  }
`

export const REFRESH_TOKEN_MUTATION = `
  mutation RefreshToken($refreshToken: String!) {
    refreshToken(refreshToken: $refreshToken) {
      accessToken
      refreshToken
      tokenType
      expiresIn
    }
  }
`
